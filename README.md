# VectorizeAI - AI-Powered Image Vectorization Platform

Transform raster images into crisp, scalable SVG files using AI-powered upscaling and vectorization.

## 🚀 Features

- **AI-Powered Processing**: Waifu2x upscaling + VTracer vectorization
- **User Management**: Registration, authentication, and profiles
- **Subscription System**: Stripe-integrated billing with multiple plans
- **Coin-Based Usage**: Track and limit usage per subscription
- **Admin Dashboard**: Comprehensive management interface
- **RESTful API**: Programmatic access to vectorization services
- **Responsive Design**: Works on desktop and mobile devices

## 🛠️ Installation

### Prerequisites
- PHP 7.4+ with extensions: PDO, cURL, GD, fileinfo
- MySQL 5.7+ or MariaDB 10.3+
- Python 3.8+ with pip
- Waifu2x-ncnn-vulkan executable

### Quick Start

1. **Clone and setup:**
   \`\`\`bash
   git clone <your-repo>
   cd vectorizeai
   chmod +x setup_production.sh
   ./setup_production.sh
   \`\`\`

2. **Configure database:**
   - Import `my.sql` into your MySQL database
   - Update database credentials in `php/config.php`

3. **Start Python API:**
   \`\`\`bash
   cd python/api
   .\start_api.bat    # Windows
   ./start_api.sh     # Linux/Mac
   \`\`\`

4. **Configure Stripe:**
   - Add your Stripe keys to `php/config.php`
   - Set up webhook endpoint: `/php/stripe_webhook.php`

5. **Test the system:**
   \`\`\`bash
   php php/test_api_simple.php
   \`\`\`

## 🔧 Usage

### For Users
1. Register an account at `/register.php`
2. Choose a subscription plan at `/pricing.php`
3. Upload images via the dashboard at `/dashboard.php`
4. Download generated SVG files

### For Developers
\`\`\`php
// Use the Python API client
$client = new PythonApiClient('http://localhost:5000');
$result = $client->vectorizeFile('/path/to/image.png');
\`\`\`

## 📊 System Status

Check system health at `/php/system_status.php` (admin only)

## 🔒 Security Features

- CSRF protection on all forms
- SQL injection prevention with prepared statements
- File upload validation and sanitization
- Rate limiting on API endpoints
- Secure session management
- XSS protection headers

## 🚀 Production Deployment

### Windows Service (Recommended)
\`\`\`bash
cd python/api
.\install_service.bat    # Run as Administrator
\`\`\`

### Manual Start
\`\`\`bash
cd python/api
.\start_api.bat
\`\`\`

### Monitoring
\`\`\`bash
.\monitor.sh           # Check system status
.\backup.sh           # Create backup
\`\`\`

## 📁 Project Structure

\`\`\`
vectorizeai/
├── assets/           # CSS, JS, images
├── php/             # PHP backend
│   ├── auth/        # Authentication
│   ├── admin/       # Admin panel
│   └── api/         # API endpoints
├── python/          # Python processing
│   └── api/         # Flask API server
├── uploads/         # Temporary uploads
├── outputs/         # Generated SVG files
└── scripts/         # Database scripts
\`\`\`

## 🔧 Configuration

Key configuration files:
- `php/config.php` - Main application config
- `python/api/app.py` - Python API settings
- `.htaccess` - Web server rules

## 🐛 Troubleshooting

### API Not Working
1. Check if Python API is running: `http://localhost:5000/health`
2. Verify Waifu2x path in `python/api/app.py`
3. Check logs in `python/api/api.log`

### Upload Issues
1. Check file permissions on `uploads/` and `outputs/` directories
2. Verify PHP upload limits in `.htaccess`
3. Check available disk space

### Database Errors
1. Verify database credentials in `php/config.php`
2. Ensure all tables are created from `my.sql`
3. Check MySQL error logs

## 📝 License

MIT License - see LICENSE file for details

## 🤝 Support

For support, check the system status page or contact your administrator.
cd python/api
.\start_api.bat