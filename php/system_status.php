<?php
require_once 'utils.php';
redirectIfNotAdmin();

// Check Python API status
function checkPythonAPI() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:5000/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return [
            'status' => 'online',
            'data' => $data
        ];
    } else {
        return [
            'status' => 'offline',
            'error' => 'API not responding'
        ];
    }
}

$apiStatus = checkPythonAPI();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - VectorizeAI</title>
    <link rel="icon" href="../assets/images/vectra-hub-logo2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">🔧 System Status</h1>
                
                <!-- Python API Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>🐍 Python API Status</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($apiStatus['status'] === 'online'): ?>
                            <div class="alert alert-success">
                                <h6>✅ API is Online</h6>
                                <p class="mb-1"><strong>Status:</strong> <?php echo $apiStatus['data']['status']; ?></p>
                                <p class="mb-1"><strong>Waifu2x Available:</strong> <?php echo $apiStatus['data']['waifu2x_available'] ? 'Yes' : 'No'; ?></p>
                                <p class="mb-0"><strong>Directories:</strong> 
                                    Uploads: <?php echo $apiStatus['data']['directories']['uploads'] ? '✅' : '❌'; ?>, 
                                    Outputs: <?php echo $apiStatus['data']['directories']['outputs'] ? '✅' : '❌'; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h6>❌ API is Offline</h6>
                                <p class="mb-0">The Python API is not responding. Please start it manually:</p>
                                <code>cd python/api && .\start_api.bat</code>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Database Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>🗄️ Database Status</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $pdo = connectDB();
                            $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
                            $userCount = $stmt->fetchColumn();
                            
                            $stmt = $pdo->query("SELECT COUNT(*) as job_count FROM image_jobs");
                            $jobCount = $stmt->fetchColumn();
                            
                            echo '<div class="alert alert-success">';
                            echo '<h6>✅ Database is Connected</h6>';
                            echo "<p class=\"mb-1\"><strong>Total Users:</strong> $userCount</p>";
                            echo "<p class=\"mb-0\"><strong>Total Jobs:</strong> $jobCount</p>";
                            echo '</div>';
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">';
                            echo '<h6>❌ Database Connection Failed</h6>';
                            echo '<p class="mb-0">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- File System Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>📁 File System Status</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $uploadDir = __DIR__ . '/../uploads/';
                        $outputDir = __DIR__ . '/../outputs/';
                        
                        $uploadExists = is_dir($uploadDir) && is_writable($uploadDir);
                        $outputExists = is_dir($outputDir) && is_writable($outputDir);
                        
                        $uploadCount = $uploadExists ? count(glob($uploadDir . '*')) : 0;
                        $outputCount = $outputExists ? count(glob($outputDir . '*.svg')) : 0;
                        ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert <?php echo $uploadExists ? 'alert-success' : 'alert-danger'; ?>">
                                    <h6><?php echo $uploadExists ? '✅' : '❌'; ?> Uploads Directory</h6>
                                    <p class="mb-0">Files: <?php echo $uploadCount; ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert <?php echo $outputExists ? 'alert-success' : 'alert-danger'; ?>">
                                    <h6><?php echo $outputExists ? '✅' : '❌'; ?> Outputs Directory</h6>
                                    <p class="mb-0">SVG Files: <?php echo $outputCount; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5>⚡ Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="dashboard.php" class="btn btn-primary w-100 mb-2">📊 Dashboard</a>
                            </div>
                            <div class="col-md-3">
                                <button onclick="location.reload()" class="btn btn-secondary w-100 mb-2">🔄 Refresh Status</button>
                            </div>
                            <div class="col-md-3">
                                <a href="http://localhost:5000/health" target="_blank" class="btn btn-info w-100 mb-2">🔗 API Health</a>
                            </div>
                            <div class="col-md-3">
                                <a href="php/admin/" class="btn btn-accent w-100 mb-2">⚙️ Admin Panel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
