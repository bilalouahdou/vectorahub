#!/bin/bash
# Production setup script for VectorizeAI

echo "🚀 Setting up VectorizeAI for Production"
echo "========================================"

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p uploads outputs logs backups
chmod 755 uploads outputs
chmod 700 logs backups

# Set proper permissions
echo "🔒 Setting permissions..."
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Secure sensitive files
chmod 600 php/config.php
chmod 600 .env 2>/dev/null || true

# Setup Python API
echo "🐍 Setting up Python API..."
cd python/api
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
cd ../..

# Create systemd service for Python API (optional)
echo "⚙️ Creating systemd service..."
cat > /tmp/vectorizeai-api.service << EOF
[Unit]
Description=VectorizeAI Python API
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=$(pwd)/python/api
Environment=PATH=$(pwd)/python/api/venv/bin
ExecStart=$(pwd)/python/api/venv/bin/python run.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

echo "📋 Systemd service created at /tmp/vectorizeai-api.service"
echo "   To install: sudo cp /tmp/vectorizeai-api.service /etc/systemd/system/"
echo "   To enable: sudo systemctl enable vectorizeai-api"
echo "   To start: sudo systemctl start vectorizeai-api"

# Create backup script
echo "💾 Creating backup script..."
cat > backup.sh << 'EOF'
#!/bin/bash
# VectorizeAI Backup Script

BACKUP_DIR="backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="vectorizeai_backup_$DATE.tar.gz"

echo "Creating backup: $BACKUP_FILE"

# Create backup
tar -czf "$BACKUP_DIR/$BACKUP_FILE" \
    --exclude="backups" \
    --exclude="uploads" \
    --exclude="python/api/venv" \
    --exclude="python/api/__pycache__" \
    --exclude="python/api/*.log" \
    .

# Database backup
mysqldump -u root -p vector > "$BACKUP_DIR/database_$DATE.sql"

echo "Backup completed: $BACKUP_DIR/$BACKUP_FILE"

# Keep only last 7 backups
find "$BACKUP_DIR" -name "vectorizeai_backup_*.tar.gz" -mtime +7 -delete
find "$BACKUP_DIR" -name "database_*.sql" -mtime +7 -delete
EOF

chmod +x backup.sh

# Create monitoring script
echo "📊 Creating monitoring script..."
cat > monitor.sh << 'EOF'
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
fi

# Check disk space
echo "💾 Disk Usage:"
df -h . | tail -1 | awk '{print "   Used: " $3 " / " $2 " (" $5 ")"}'

# Check upload/output directories
echo "📁 Directory Status:"
echo "   Uploads: $(ls uploads/ 2>/dev/null | wc -l) files"
echo "   Outputs: $(ls outputs/ 2>/dev/null | wc -l) files"

# Check database connection
echo "🗄️  Database Status:"
if mysql -u root -p$DB_PASS -e "USE vector; SELECT COUNT(*) FROM users;" > /dev/null 2>&1; then
    echo "   ✅ Database is accessible"
else
    echo "   ❌ Database connection failed"
fi

# Check log files
echo "📋 Recent Errors:"
if [ -f "python/api/api.log" ]; then
    tail -5 python/api/api.log | grep -i error || echo "   ✅ No recent errors"
else
    echo "   ℹ️  No log file found"
fi
EOF

chmod +x monitor.sh

echo ""
echo "✅ Production setup completed!"
echo ""
echo "📋 Next Steps:"
echo "1. Update Stripe keys in php/config.php"
echo "2. Configure your domain in \$APP_URL"
echo "3. Set up SSL certificate (Let's Encrypt recommended)"
echo "4. Configure your web server (Apache/Nginx)"
echo "5. Set up automated backups: ./backup.sh"
echo "6. Monitor system health: ./monitor.sh"
echo ""
echo "🔧 Optional:"
echo "- Install systemd service for Python API"
echo "- Set up log rotation"
echo "- Configure email notifications"
echo "- Set up monitoring alerts"
