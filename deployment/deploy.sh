#!/bin/bash
# VectorizeAI Production Deployment Script

echo "🚀 VectorizeAI Production Deployment"
echo "===================================="

# Configuration
DOMAIN="your-domain.com"
APP_DIR="/var/www/vectorizeai"
PYTHON_API_DIR="$APP_DIR/python/api"
SERVICE_NAME="vectorizeai-api"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root for security reasons"
   exit 1
fi

print_status "Starting VectorizeAI deployment..."

# 1. Update system packages
print_status "Updating system packages..."
sudo apt update && sudo apt upgrade -y

# 2. Install required packages
print_status "Installing required packages..."
sudo apt install -y \
    nginx \
    mysql-server \
    php8.1 \
    php8.1-fpm \
    php8.1-mysql \
    php8.1-curl \
    php8.1-gd \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-zip \
    python3 \
    python3-pip \
    python3-venv \
    git \
    curl \
    unzip \
    supervisor \
    certbot \
    python3-certbot-nginx

# 3. Create application directory
print_status "Creating application directory..."
sudo mkdir -p $APP_DIR
sudo chown $USER:www-data $APP_DIR
sudo chmod 755 $APP_DIR

# 4. Copy application files
print_status "Copying application files..."
cp -r . $APP_DIR/
cd $APP_DIR

# 5. Set proper permissions
print_status "Setting file permissions..."
sudo chown -R $USER:www-data $APP_DIR
sudo chmod -R 755 $APP_DIR
sudo chmod -R 775 $APP_DIR/uploads
sudo chmod -R 775 $APP_DIR/outputs
sudo chmod 600 $APP_DIR/php/config.php

# 6. Setup Python environment
print_status "Setting up Python environment..."
cd $PYTHON_API_DIR
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# 7. Install Waifu2x (optional but recommended)
print_status "Installing Waifu2x..."
cd /tmp
wget https://github.com/nihui/waifu2x-ncnn-vulkan/releases/download/20220728/waifu2x-ncnn-vulkan-20220728-ubuntu.zip
unzip waifu2x-ncnn-vulkan-20220728-ubuntu.zip
sudo mv waifu2x-ncnn-vulkan-20220728-ubuntu /opt/waifu2x
sudo chmod +x /opt/waifu2x/waifu2x-ncnn-vulkan

# Update config to point to correct waifu2x path
sed -i 's|C:\\waifu2x-ncnn-vulkan-20230413-win64|/opt/waifu2x|g' $APP_DIR/python/api/app.py

# 8. Create systemd service for Python API
print_status "Creating systemd service..."
sudo tee /etc/systemd/system/$SERVICE_NAME.service > /dev/null <<EOF
[Unit]
Description=VectorizeAI Python API
After=network.target

[Service]
Type=simple
User=$USER
Group=www-data
WorkingDirectory=$PYTHON_API_DIR
Environment=PATH=$PYTHON_API_DIR/venv/bin
ExecStart=$PYTHON_API_DIR/venv/bin/python run.py
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

# 9. Configure Nginx
print_status "Configuring Nginx..."
sudo tee /etc/nginx/sites-available/vectorizeai > /dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $APP_DIR;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # File upload limits
    client_max_body_size 10M;

    # PHP handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    # Allow file_proxy.php for image serving
    location ~ ^/php/api/file_proxy\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Allow other API endpoints  
    location ~ ^/php/api/.*\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block other php/scripts/python access
    location ~ /(php|scripts|python)/ {
        deny all;
    }

    # Allow download endpoint
    location /download.php {
        try_files \$uri =404;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    # Python API proxy
    location /api/ {
        proxy_pass http://127.0.0.1:5000/;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
    }

    # Main location
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
}
EOF

# Enable site
sudo ln -sf /etc/nginx/sites-available/vectorizeai /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# 10. Configure PHP-FPM
print_status "Configuring PHP-FPM..."
sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /etc/php/8.1/fpm/php.ini
sudo sed -i 's/post_max_size = 8M/post_max_size = 10M/' /etc/php/8.1/fpm/php.ini
sudo sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.1/fpm/php.ini
sudo sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php/8.1/fpm/php.ini

# 11. Setup MySQL database
print_status "Setting up MySQL database..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS vector;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'vectorai'@'localhost' IDENTIFIED BY 'secure_password_here';"
sudo mysql -e "GRANT ALL PRIVILEGES ON vector.* TO 'vectorai'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Import database schema
mysql -u vectorai -p vector < $APP_DIR/my.sql

# 12. Update configuration files
print_status "Updating configuration..."
cat > $APP_DIR/php/config.php <<EOF
<?php
// Database Configuration
\$DB_HOST = 'localhost';
\$DB_USER = 'vectorai';
\$DB_PASS = 'secure_password_here';
\$DB_NAME = 'vector';

// App Configuration
\$APP_NAME = 'VectorizeAI';
\$APP_URL = 'https://$DOMAIN';
\$UPLOAD_DIR = __DIR__ . '/../uploads/';
\$OUTPUT_DIR = __DIR__ . '/../outputs/';

// Python API Configuration
\$PYTHON_API_URL = 'http://localhost:5000';

// Stripe Configuration (Replace with your actual keys)
\$STRIPE_PUBLISHABLE_KEY = 'pk_live_your_publishable_key_here';
\$STRIPE_SECRET_KEY = 'sk_live_your_secret_key_here';
\$STRIPE_WEBHOOK_SECRET = 'whsec_your_webhook_secret_here';

// Security Configuration
\$CSRF_TOKEN_EXPIRY = 3600;
\$SESSION_LIFETIME = 86400;
\$MAX_LOGIN_ATTEMPTS = 5;
\$LOGIN_LOCKOUT_TIME = 900;

// File Upload Limits
\$MAX_FILE_SIZE = 5 * 1024 * 1024;
\$ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg'];

// Rate Limiting
\$RATE_LIMIT_REQUESTS = 10;
\$RATE_LIMIT_WINDOW = 60;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => \$SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Generate CSRF token
if (!isset(\$_SESSION['csrf_token'])) {
    \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Create directories
if (!file_exists(\$UPLOAD_DIR)) {
    mkdir(\$UPLOAD_DIR, 0755, true);
}
if (!file_exists(\$OUTPUT_DIR)) {
    mkdir(\$OUTPUT_DIR, 0755, true);
}
?>
EOF

# 13. Start services
print_status "Starting services..."
sudo systemctl daemon-reload
sudo systemctl enable $SERVICE_NAME
sudo systemctl start $SERVICE_NAME
sudo systemctl enable nginx
sudo systemctl restart nginx
sudo systemctl enable php8.1-fpm
sudo systemctl restart php8.1-fpm

# 14. Setup SSL certificate
print_status "Setting up SSL certificate..."
sudo certbot --nginx -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN

# 15. Setup log rotation
print_status "Setting up log rotation..."
sudo tee /etc/logrotate.d/vectorizeai > /dev/null <<EOF
$APP_DIR/python/api/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 $USER www-data
    postrotate
        systemctl reload $SERVICE_NAME
    endscript
}
EOF

# 16. Create monitoring script
print_status "Creating monitoring script..."
cat > $APP_DIR/monitor.sh <<'EOF'
#!/bin/bash
# VectorizeAI Monitoring Script

echo "🔍 VectorizeAI System Status"
echo "=========================="

# Check Python API
echo "🐍 Python API Status:"
if curl -s http://localhost:5000/health > /dev/null; then
    echo "   ✅ API is running"
else
    echo "   ❌ API is down"
    sudo systemctl restart vectorizeai-api
fi

# Check Nginx
echo "🌐 Nginx Status:"
if systemctl is-active --quiet nginx; then
    echo "   ✅ Nginx is running"
else
    echo "   ❌ Nginx is down"
    sudo systemctl restart nginx
fi

# Check disk space
echo "💾 Disk Usage:"
df -h / | tail -1 | awk '{print "   Used: " $3 " / " $2 " (" $5 ")"}'

# Check upload/output directories
echo "📁 Directory Status:"
echo "   Uploads: $(ls uploads/ 2>/dev/null | wc -l) files"
echo "   Outputs: $(ls outputs/ 2>/dev/null | wc -l) files"

# Check database
echo "🗄️  Database Status:"
if mysql -u vectorai -psecure_password_here -e "USE vector; SELECT COUNT(*) FROM users;" > /dev/null 2>&1; then
    echo "   ✅ Database is accessible"
else
    echo "   ❌ Database connection failed"
fi

# Check recent errors
echo "📋 Recent Errors:"
if [ -f "python/api/api.log" ]; then
    tail -5 python/api/api.log | grep -i error || echo "   ✅ No recent errors"
else
    echo "   ℹ️  No log file found"
fi
EOF

chmod +x $APP_DIR/monitor.sh

# 17. Setup cron jobs
print_status "Setting up cron jobs..."
(crontab -l 2>/dev/null; echo "0 2 * * * $APP_DIR/backup.sh") | crontab -
(crontab -l 2>/dev/null; echo "*/5 * * * * $APP_DIR/monitor.sh") | crontab -

# 18. Create backup script
cat > $APP_DIR/backup.sh <<'EOF'
#!/bin/bash
# VectorizeAI Backup Script

BACKUP_DIR="$APP_DIR/backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="vectorizeai_backup_$DATE.tar.gz"

mkdir -p "$BACKUP_DIR"

echo "Creating backup: $BACKUP_FILE"

# Create backup
tar -czf "$BACKUP_DIR/$BACKUP_FILE" \
    --exclude="backups" \
    --exclude="uploads" \
    --exclude="python/api/venv" \
    --exclude="python/api/__pycache__" \
    --exclude="python/api/*.log" \
    $APP_DIR

# Database backup
mysqldump -u vectorai -psecure_password_here vector > "$BACKUP_DIR/database_$DATE.sql"

echo "Backup completed: $BACKUP_DIR/$BACKUP_FILE"

# Keep only last 7 backups
find "$BACKUP_DIR" -name "vectorizeai_backup_*.tar.gz" -mtime +7 -delete
find "$BACKUP_DIR" -name "database_*.sql" -mtime +7 -delete
EOF

chmod +x $APP_DIR/backup.sh

print_status "✅ Deployment completed successfully!"
echo ""
echo "🎉 VectorizeAI is now deployed and accessible at:"
echo "   https://$DOMAIN"
echo ""
echo "📋 Next Steps:"
echo "1. Update Stripe keys in $APP_DIR/php/config.php"
echo "2. Test the application thoroughly"
echo "3. Monitor logs: sudo journalctl -u $SERVICE_NAME -f"
echo "4. Check system status: $APP_DIR/monitor.sh"
echo ""
echo "🔧 Important Files:"
echo "   - Application: $APP_DIR"
echo "   - Nginx config: /etc/nginx/sites-available/vectorizeai"
echo "   - Service config: /etc/systemd/system/$SERVICE_NAME.service"
echo "   - Logs: sudo journalctl -u $SERVICE_NAME"
echo ""
echo "🚨 Security Reminders:"
echo "   - Change default database password"
echo "   - Update Stripe API keys"
echo "   - Configure firewall (ufw enable)"
echo "   - Regular security updates"
