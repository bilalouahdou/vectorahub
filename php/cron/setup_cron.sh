#!/bin/bash

# Setup cron jobs for VectraHub
# Run this script once to install cron jobs

echo "Setting up VectraHub cron jobs..."

# Create cron job file
CRON_FILE="/tmp/vectrahub_cron"

# Subscription expiration check - daily at 9 AM
echo "0 9 * * * /usr/bin/php /var/www/html/php/cron/check_expired_subscriptions.php >> /var/www/html/logs/cron.log 2>&1" > $CRON_FILE

# Weekly cleanup - Sundays at 2 AM
echo "0 2 * * 0 /usr/bin/php /var/www/html/php/cron/weekly_cleanup.php >> /var/www/html/logs/cron.log 2>&1" >> $CRON_FILE

# System health check - every 6 hours
echo "0 */6 * * * /usr/bin/php /var/www/html/php/cron/health_check.php >> /var/www/html/logs/cron.log 2>&1" >> $CRON_FILE

# Install cron jobs
crontab $CRON_FILE

# Clean up
rm $CRON_FILE

echo "Cron jobs installed successfully!"
echo "Current cron jobs:"
crontab -l

# Create log directory if it doesn't exist
mkdir -p /var/www/html/logs
chmod 755 /var/www/html/logs

echo "Setup complete!"
