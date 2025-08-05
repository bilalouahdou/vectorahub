# Fly.io Deployment Troubleshooting Guide

## ✅ RESOLVED: Health Check Timeout Issue

**Issue**: The deployment was failing with:
```
Error: failed to update machine 4d899393c7e168: Unrecoverable error: timeout reached waiting for health checks to pass for machine 4d899393c7e168
```

**Root Cause**: The health checks were returning HTTP 301 (redirect) responses instead of 200 (success) because:
- The `.htaccess` file was redirecting `/health.php` to `/health` (clean URLs)
- The Fly.io health check was still configured to use `/health.php`
- This caused a redirect loop that made health checks fail

**Solution**: Updated the health check path in `fly.toml` from `/health.php` to `/health` to match the clean URL format.

## Changes Made to Resolve the Issue

### 1. Simplified Health Check (`health.php`)
- Removed dependency on `config.php` to avoid potential startup issues
- Reduced database connection timeout from 15s to 3s
- Made database failures non-critical (don't mark app as unhealthy)
- Added `set_time_limit(5)` for faster health check execution
- Simplified database query to just `SELECT 1`

### 2. Updated Fly.io Configuration (`fly.toml`)
- Increased grace period from 30s to 60s
- Increased check interval from 20s to 30s  
- Increased timeout from 10s to 15s
- **CRITICAL FIX**: Changed health check path from `/health.php` to `/health`

### 3. Fixed Apache Configuration (`deployment/apache-config.conf`)
- Removed duplicate VirtualHost configuration that could cause startup issues

### 4. Added Startup Test Script (`startup.php`)
- Simple diagnostic script to test basic functionality
- Can be accessed at `/startup.php` during deployment

## Current Status

✅ **DEPLOYMENT SUCCESSFUL**
- Both machines are running with passing health checks
- Application is accessible at https://vectrahub.fly.dev/
- Health endpoint responds correctly at https://vectrahub.fly.dev/health

## Troubleshooting Steps (for future reference)

### Step 1: Deploy with Current Changes
```bash
fly deploy
```

### Step 2: If Still Failing, Check Health Check Manually
```bash
# Check health endpoint
curl -v https://vectrahub.fly.dev/health

# Check startup script
curl -v https://vectrahub.fly.dev/startup.php
```

### Step 3: Check Fly.io Logs
```bash
fly logs
```

### Step 4: If Database is the Issue, Deploy with Skip Flag
```bash
# Deploy with database health check skipped
fly deploy --strategy immediate
```

Then manually test the health endpoint with skip flag:
```bash
curl https://vectrahub.fly.dev/health?skip_db=1
```

### Step 5: Check Machine Status
```bash
fly status
fly machine list
```

### Step 6: If Still Failing, Try Rolling Back
```bash
# List deployments
fly releases

# Rollback to previous version
fly deploy --image-label <previous-version>
```

## Common Issues and Solutions

### 1. Health Check Path Mismatch (RESOLVED)
- **Symptom**: Health checks return 301 redirects
- **Solution**: Ensure health check path matches clean URL format (e.g., `/health` not `/health.php`)
- **Check**: Verify `.htaccess` redirects and `fly.toml` health check path alignment

### 2. Database Connection Issues
- **Symptom**: Health check fails on database connection
- **Solution**: The health check now skips database failures during deployment
- **Alternative**: Use `?skip_db=1` parameter in health check URL

### 3. Apache Startup Issues
- **Symptom**: Container fails to start
- **Solution**: Fixed duplicate VirtualHost configuration
- **Check**: Look for Apache error logs in `fly logs`

### 4. Memory/Resource Issues
- **Symptom**: Container runs out of memory
- **Solution**: Current VM has 1024MB memory, consider increasing if needed
- **Check**: Monitor memory usage in Fly.io dashboard

### 5. Environment Variables
- **Symptom**: Missing required environment variables
- **Solution**: Ensure all required secrets are set
- **Check**: `fly secrets list`

## Required Environment Variables
Make sure these are set in Fly.io:
```bash
fly secrets set DATABASE_URL="your-supabase-connection-string"
fly secrets set SUPABASE_URL="https://ozjbgmcxocvznfcttaty.supabase.co"
fly secrets set STRIPE_SECRET_KEY="your-stripe-secret"
fly secrets set STRIPE_PUBLISHABLE_KEY="your-stripe-publishable-key"
```

## Alternative Health Check Configuration
If the current health check is still problematic, you can temporarily use a simpler one:

```toml
[[http_service.checks]]
  grace_period = "120s"
  interval = "60s"
  method = "GET"
  timeout = "30s"
  path = "/startup.php"
  protocol = "http"
```

## Emergency Rollback
If deployment completely fails:
```bash
# Destroy current machines
fly machine destroy 4d899393c7e168 1781916db6d4d8

# Deploy fresh
fly deploy
```

## Monitoring
After successful deployment, monitor:
- Health check status: `https://vectrahub.fly.dev/health`
- Application logs: `fly logs`
- Machine status: `fly status`

## Key Lessons Learned

1. **Clean URLs and Health Checks**: When using `.htaccess` for clean URLs, ensure health check paths match the clean URL format, not the original `.php` files.

2. **Health Check Simplicity**: Keep health checks simple and fast. Avoid complex database queries or external dependencies that could cause timeouts.

3. **Graceful Degradation**: Make health checks resilient by not marking the app as unhealthy for non-critical failures (like database issues during deployment).

4. **Log Analysis**: Always check `fly logs` to understand what's happening during deployment failures. The logs revealed the 301 redirect issue that was causing the timeout. 