# VectraHub Email System Setup Guide

## 🚀 Complete Resend Integration for PHP

### 1. Set Fly.io Secrets

```bash
# Set Resend API key
fly secrets set RESEND_API_KEY=re_hzYtNgk1_HW2JkU65FMuKf1fcwf3TAVcd

# Optional: Set webhook secret for security
fly secrets set RESEND_WEBHOOK_SECRET=your_webhook_secret_here

# Optional: Set cron security key
fly secrets set CRON_SECRET_KEY=your_random_cron_key_here

# Deploy with new secrets
fly deploy
```

### 2. Database Setup

```bash
# Run email tables migration
psql $DATABASE_URL -f scripts/email_tables.sql
```

### 3. DNS Configuration

#### Required DNS Records for vectrahub.online:

```dns
# DMARC Policy (start with monitoring)
_dmarc.vectrahub.online    TXT    v=DMARC1; p=none; rua=mailto:dmarc@vectrahub.online

# SPF (if not already set)
vectrahub.online          TXT    v=spf1 include:_spf.resend.com ~all

# DKIM (if not already set - check Resend dashboard)
resend._domainkey.vectrahub.online    CNAME    resend.vectrahub.online
```

### 4. Configure Error Handling

Add to your main entry points (index.php, dashboard.php, etc.):

```php
<?php
require_once 'php/init_error_handler.php';
// ... rest of your code
```

### 5. Test Email System

```bash
# Test welcome email
curl -X POST https://vectrahub.online/php/auth/register \
  -d "full_name=Test User&email=test@example.com&password=test123&confirm_password=test123&csrf_token=..."

# Test subscription check (manual trigger)
curl "https://vectrahub.online/php/cron/check_expired_subscriptions.php?cron_key=your_cron_key"

# Test incident email (trigger an error)
curl https://vectrahub.online/test_error.php
```

### 6. Setup Cron Jobs

```bash
# Make setup script executable
chmod +x php/cron/setup_cron.sh

# Run setup (on your server)
./php/cron/setup_cron.sh
```

### 7. Configure Resend Webhooks

In your Resend dashboard:
- **Webhook URL**: `https://vectrahub.online/php/webhooks/resend.php`
- **Events**: Select all (sent, delivered, bounced, complained, opened, clicked)

### 8. DMARC Hardening Timeline

```
Week 1-2: Monitor with p=none
Week 3-4: Change to p=quarantine  
Week 5+:  Change to p=reject (strict)
```

Update DMARC after monitoring:
```dns
_dmarc.vectrahub.online    TXT    v=DMARC1; p=quarantine; rua=mailto:dmarc@vectrahub.online
```

## 📧 Email Flows Implemented

### 1. Welcome + Verification
- ✅ Sent on user registration
- ✅ Professional HTML template
- ✅ Email verification link
- ✅ Error handling (doesn't break registration)

### 2. Subscription Expired
- ✅ Daily cron job checks
- ✅ Sends warning 3 days before expiry
- ✅ Sends expired notification 
- ✅ Rate limited (once per week max)

### 3. Incident Notifications
- ✅ Global error handler
- ✅ Catches PHP errors/exceptions
- ✅ Rate limited (1 per hour per error type)
- ✅ User-friendly error pages

## 🛠 Files Created

```
php/services/EmailService.php           # Main email service
php/templates/email/welcome.html        # Welcome email template
php/templates/email/subscription_expired.html  # Subscription email
php/templates/email/incident.html       # Incident alert template
php/middleware/ErrorHandler.php         # Global error handler
php/webhooks/resend.php                 # Webhook handler
php/cron/check_expired_subscriptions.php # Daily subscription check
php/init_error_handler.php              # Error handler initializer
verify-email.php                        # Email verification page
scripts/email_tables.sql                # Database schema
```

## 🧪 Testing Commands

```bash
# Test email service
php -r "
require_once 'php/services/EmailService.php';
\$service = new EmailService();
\$result = \$service->sendEmail('test@example.com', 'Test', '<h1>Test Email</h1>');
var_dump(\$result);
"

# Test webhook
curl -X POST https://vectrahub.online/php/webhooks/resend.php \
  -H "Content-Type: application/json" \
  -d '{"type":"email.sent","data":{"id":"test","to":["test@example.com"]}}'

# Check logs
tail -f logs/php_errors.log
tail -f logs/subscription_check.log
```

## 📊 Monitoring

- **Email Events**: Check `email_events` table
- **Bounces**: Monitor `users.email_bounced` column  
- **Complaints**: Monitor `users.email_complained` column
- **Logs**: `/logs/php_errors.log`, `/logs/subscription_check.log`

## 🔒 Security Features

- ✅ Webhook signature verification
- ✅ CSRF token validation
- ✅ Rate limiting on incident emails
- ✅ Cron job security key
- ✅ Error handling without data exposure
- ✅ SQL injection prevention
- ✅ Input sanitization

---

**🎉 Your VectraHub email system is now fully integrated with Resend!**
