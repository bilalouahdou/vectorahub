<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - VectorizeAI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
</head>
<body>
    <?php require_once 'php/config.php'; ?>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <span class="text-accent">Vector</span>izeAI
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/">Home</a>
                <a class="nav-link" href="pricing.php">Pricing</a>
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold">API Documentation</h1>
            <p class="lead text-muted">Integrate VectorizeAI into your applications</p>
        </div>

        <!-- Quick Start -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="admin-card">
                    <h2>🚀 Quick Start</h2>
                    <p>Our API is currently running locally and ready for integration. Here's how to get started:</p>
                    
                    <div class="alert alert-info">
                        <h5>✅ API Status</h5>
                        <p class="mb-0">Your Python API is working! The test showed successful file upload and vectorization.</p>
                    </div>
                    
                    <h4>Base URL</h4>
                    <pre><code>http://localhost:5000</code></pre>
                    
                    <h4>Authentication</h4>
                    <p>Currently, the API runs locally without authentication. For production use, you would implement API key authentication.</p>
                </div>
            </div>
        </div>

        <!-- Endpoints -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="admin-card">
                    <h2>📡 API Endpoints</h2>
                    
                    <!-- Health Check -->
                    <div class="mb-4">
                        <h3 class="text-accent">GET /health</h3>
                        <p>Check API status and availability.</p>
                        
                        <h5>Response:</h5>
                        <pre><code class="language-json">{
  "status": "healthy",
  "waifu2x_available": true,
  "directories": {
    "uploads": true,
    "outputs": true
  }
}</code></pre>
                    </div>

                    <!-- Vectorize File -->
                    <div class="mb-4">
                        <h3 class="text-accent">POST /vectorize</h3>
                        <p>Vectorize an image using file upload or URL.</p>
                        
                        <h5>File Upload:</h5>
                        <pre><code class="language-bash">curl -X POST http://localhost:5000/vectorize \
  -F "image=@/path/to/your/image.png"</code></pre>
                        
                        <h5>URL Input:</h5>
                        <pre><code class="language-bash">curl -X POST http://localhost:5000/vectorize \
  -H "Content-Type: application/json" \
  -d '{"image_url": "https://example.com/image.png"}'</code></pre>
                        
                        <h5>Success Response:</h5>
                        <pre><code class="language-json">{
  "success": true,
  "svg_filename": "abc123.svg",
  "download_url": "/download/abc123.svg"
}</code></pre>
                        
                        <h5>Error Response:</h5>
                        <pre><code class="language-json">{
  "success": false,
  "error": "Error message here"
}</code></pre>
                    </div>

                    <!-- Download -->
                    <div class="mb-4">
                        <h3 class="text-accent">GET /download/&lt;filename&gt;</h3>
                        <p>Download a generated SVG file.</p>
                        
                        <h5>Example:</h5>
                        <pre><code class="language-bash">curl -O http://localhost:5000/download/abc123.svg</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Code Examples -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="admin-card">
                    <h2>💻 Code Examples</h2>
                    
                    <!-- PHP Example -->
                    <div class="mb-4">
                        <h3>PHP</h3>
                        <pre><code class="language-php">&lt;?php
// Using the PythonApiClient class
require_once 'php/python_api_client.php';

$client = new PythonApiClient('http://localhost:5000');

try {
    // Vectorize a file
    $result = $client->vectorizeFile('/path/to/image.png');
    
    if ($result['success']) {
        echo "SVG created: " . $result['svg_filename'];
        
        // Download the SVG
        $client->downloadSvg($result['svg_filename'], '/path/to/save/output.svg');
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?&gt;</code></pre>
                    </div>

                    <!-- JavaScript Example -->
                    <div class="mb-4">
                        <h3>JavaScript</h3>
                        <pre><code class="language-javascript">// File upload
const formData = new FormData();
formData.append('image', fileInput.files[0]);

fetch('http://localhost:5000/vectorize', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('SVG created:', data.svg_filename);
        // Download the SVG
        window.open(`http://localhost:5000/download/${data.svg_filename}`);
    } else {
        console.error('Error:', data.error);
    }
})
.catch(error => console.error('Network error:', error));</code></pre>
                    </div>

                    <!-- Python Example -->
                    <div class="mb-4">
                        <h3>Python</h3>
                        <pre><code class="language-python">import requests

# File upload
with open('/path/to/image.png', 'rb') as f:
    files = {'image': f}
    response = requests.post('http://localhost:5000/vectorize', files=files)

if response.status_code == 200:
    data = response.json()
    if data['success']:
        print(f"SVG created: {data['svg_filename']}")
        
        # Download the SVG
        svg_response = requests.get(f"http://localhost:5000/download/{data['svg_filename']}")
        with open('output.svg', 'wb') as f:
            f.write(svg_response.content)
    else:
        print(f"Error: {data['error']}")
else:
    print(f"HTTP Error: {response.status_code}")</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Codes -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="admin-card">
                    <h2>⚠️ Error Codes</h2>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>HTTP Code</th>
                                    <th>Error Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>400</td>
                                    <td>Bad Request</td>
                                    <td>Invalid file type, missing parameters, or malformed request</td>
                                </tr>
                                <tr>
                                    <td>404</td>
                                    <td>Not Found</td>
                                    <td>Requested file or endpoint not found</td>
                                </tr>
                                <tr>
                                    <td>413</td>
                                    <td>Payload Too Large</td>
                                    <td>File size exceeds 5MB limit</td>
                                </tr>
                                <tr>
                                    <td>500</td>
                                    <td>Internal Server Error</td>
                                    <td>Processing failed, Waifu2x error, or VTracer error</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rate Limits -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="admin-card">
                    <h2>🚦 Rate Limits & Guidelines</h2>
                    
                    <ul>
                        <li><strong>File Size:</strong> Maximum 5MB per image</li>
                        <li><strong>File Types:</strong> PNG, JPG, JPEG only</li>
                        <li><strong>Processing Time:</strong> Typically 10-60 seconds depending on image size</li>
                        <li><strong>Concurrent Requests:</strong> Currently no limit (local development)</li>
                        <li><strong>Storage:</strong> Generated SVGs are stored temporarily</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <h5>🔧 Development Note</h5>
                        <p class="mb-0">This API is currently running in development mode. For production deployment, consider implementing authentication, rate limiting, and proper error handling.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Testing -->
        <div class="row">
            <div class="col-12">
                <div class="admin-card">
                    <h2>🧪 Testing Your Integration</h2>
                    
                    <p>You can test the API directly from your command line:</p>
                    
                    <h5>1. Check API Health:</h5>
                    <pre><code class="language-bash">curl http://localhost:5000/health</code></pre>
                    
                    <h5>2. Test File Upload:</h5>
                    <pre><code class="language-bash">curl -X POST http://localhost:5000/vectorize \
  -F "image=@/path/to/test/image.png"</code></pre>
                    
                    <h5>3. Run PHP Test Script:</h5>
                    <pre><code class="language-bash">php php/test_api_simple.php</code></pre>
                    
                    <div class="alert alert-success">
                        <h5>✅ Your API is Ready!</h5>
                        <p class="mb-0">Based on your test results, the API is working correctly and ready for integration into your applications.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
</body>
</html>
