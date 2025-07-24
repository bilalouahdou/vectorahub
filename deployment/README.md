# VectorizeAI Deployment Guide

## 🚀 Deployment Options

### Option 1: Traditional VPS/Server Deployment

#### Requirements:
- **VPS/Server**: Ubuntu 22.04 LTS (2GB RAM minimum, 4GB recommended)
- **Domain**: Registered domain pointing to your server IP
- **SSL**: Let's Encrypt (free) or paid certificate

#### Quick Deployment:
\`\`\`bash
# 1. Clone your repository
git clone https://github.com/yourusername/vectorizeai.git
cd vectorizeai

# 2. Make deployment script executable
chmod +x deployment/deploy.sh

# 3. Edit the domain in deploy.sh
nano deployment/deploy.sh
# Change DOMAIN="your-domain.com" to your actual domain

# 4. Run deployment
./deployment/deploy.sh
\`\`\`

#### Manual Steps After Deployment:
1. **Update Stripe Keys** in `/var/www/vectorizeai/php/config.php`
2. **Change Database Password** (update in config.php and MySQL)
3. **Configure Firewall**:
   \`\`\`bash
   sudo ufw allow 22
   sudo ufw allow 80
   sudo ufw allow 443
   sudo ufw enable
   \`\`\`

### Option 2: Docker Deployment

#### Requirements:
- **Docker & Docker Compose** installed
- **Domain** pointing to your server

#### Quick Start:
\`\`\`bash
# 1. Clone repository
git clone https://github.com/yourusername/vectorizeai.git
cd vectorizeai

# 2. Update environment variables
nano deployment/docker-compose.yml
# Update passwords and domain

# 3. Deploy
docker-compose -f deployment/docker-compose.yml up -d
\`\`\`

### Option 3: Cloud Platform Deployment

#### AWS EC2:
1. **Launch EC2 Instance** (t3.medium recommended)
2. **Configure Security Groups** (ports 22, 80, 443)
3. **Run deployment script**
4. **Setup RDS** for MySQL (optional but recommended)

#### DigitalOcean Droplet:
1. **Create Droplet** (4GB RAM, Ubuntu 22.04)
2. **Point domain** to droplet IP
3. **Run deployment script**

#### Google Cloud Platform:
1. **Create Compute Engine instance**
2. **Configure firewall rules**
3. **Run deployment script**

## 🔧 Configuration

### Environment Variables:
\`\`\`bash
# Database
DB_HOST=localhost
DB_USER=vectorai
DB_PASS=your_secure_password
DB_NAME=vector

# Stripe (Production Keys)
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# App Settings
APP_URL=https://yourdomain.com
PYTHON_API_URL=http://localhost:5000
\`\`\`

### File Permissions:
\`\`\`bash
sudo chown -R www-data:www-data /var/www/vectorizeai
sudo chmod -R 755 /var/www/vectorizeai
sudo chmod -R 775 /var/www/vectorizeai/uploads
sudo chmod -R 775 /var/www/vectorizeai/outputs
sudo chmod 600 /var/www/vectorizeai/php/config.php
\`\`\`

## 📊 Monitoring & Maintenance

### System Monitoring:
\`\`\`bash
# Check all services
./monitor.sh

# Check Python API
sudo systemctl status vectorizeai-api
sudo journalctl -u vectorizeai-api -f

# Check Nginx
sudo systemctl status nginx
sudo tail -f /var/log/nginx/error.log

# Check PHP-FPM
sudo systemctl status php8.1-fpm
sudo tail -f /var/log/php8.1-fpm.log
\`\`\`

### Backup & Recovery:
\`\`\`bash
# Manual backup
./backup.sh

# Restore from backup
tar -xzf backups/vectorizeai_backup_YYYYMMDD_HHMMSS.tar.gz
mysql -u vectorai -p vector < backups/database_YYYYMMDD_HHMMSS.sql
\`\`\`

### Performance Optimization:
1. **Enable PHP OPcache**
2. **Configure Nginx caching**
3. **Setup Redis for sessions** (optional)
4. **Use CDN for static assets** (optional)

## 🔒 Security Checklist

- [ ] **SSL Certificate** installed and auto-renewing
- [ ] **Firewall** configured (only ports 22, 80, 443 open)
- [ ] **Strong passwords** for database and admin accounts
- [ ] **Regular security updates** scheduled
- [ ] **File permissions** properly set
- [ ] **Sensitive files** protected from web access
- [ ] **Rate limiting** enabled
- [ ] **CSRF protection** active
- [ ] **Input validation** implemented

## 🚨 Troubleshooting

### Common Issues:

#### Python API Not Starting:
\`\`\`bash
# Check logs
sudo journalctl -u vectorizeai-api -f

# Restart service
sudo systemctl restart vectorizeai-api

# Check Python dependencies
cd /var/www/vectorizeai/python/api
source venv/bin/activate
pip install -r requirements.txt
\`\`\`

#### File Upload Issues:
\`\`\`bash
# Check PHP settings
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Check directory permissions
ls -la uploads/ outputs/
\`\`\`

#### Database Connection Issues:
\`\`\`bash
# Test database connection
mysql -u vectorai -p vector

# Check MySQL status
sudo systemctl status mysql
\`\`\`

### Performance Issues:
1. **Monitor resource usage**: `htop`, `iotop`
2. **Check disk space**: `df -h`
3. **Monitor logs** for errors
4. **Optimize database** queries
5. **Scale server resources** if needed

## 📈 Scaling

### Horizontal Scaling:
1. **Load Balancer** (Nginx, HAProxy)
2. **Multiple App Servers**
3. **Shared Database** (RDS, managed MySQL)
4. **Shared File Storage** (NFS, S3)

### Vertical Scaling:
1. **Increase server resources**
2. **Optimize database**
3. **Enable caching**
4. **Use CDN**

## 💰 Cost Optimization

### Free Tier Options:
- **Oracle Cloud**: Always free tier (1-4 OCPUs)
- **AWS**: 12 months free tier
- **Google Cloud**: $300 credit
- **DigitalOcean**: Often has promotional credits

### Recommended Hosting:
- **Development**: DigitalOcean Droplet ($6/month)
- **Production**: AWS EC2 t3.medium ($30/month)
- **High Traffic**: AWS EC2 c5.large ($70/month)

## 📞 Support

For deployment issues:
1. Check logs first
2. Review this documentation
3. Search common issues online
4. Contact hosting provider support
5. Consider hiring a DevOps consultant
